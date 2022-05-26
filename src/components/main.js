/**
 * WordPress dependencies
 */
import { Card, CardBody, CardHeader, TabPanel } from '@wordpress/components';
/**
* Internal dependencies
*/
import { Delete, ProgressBar } from "../components";

import './main/style.scss';

export default function Main({ posts, currentProgress, isInProgress, setIsInProgress, setCurrentProgress, setPosts }) {
    
    return (
        <Card className="bca-duplicate-posts-main">
            <CardBody className="bca-duplicate-posts-content">
                <h2>Summary</h2>
                <p>We found <strong>{posts.has_duplicate.found_posts}</strong> posts that have been duplicated.</p>
                <p>Total number of duplicate posts: <strong>{posts.is_duplicate.found_posts}</strong>.</p>
                { posts.has_duplicate.found_posts > 0 && (
                    <Delete 
                        
                        currentProgress={currentProgress} 
                        isInProgress={isInProgress} 
                        posts={posts}
                        setIsInProgress={setIsInProgress}
                        setCurrentProgress={setCurrentProgress}
                        setPosts={setPosts}
                    />
                )}
                { isInProgress && (
                    <ProgressBar value={currentProgress}/>
                )}
            </CardBody>
        </Card>
    )
}