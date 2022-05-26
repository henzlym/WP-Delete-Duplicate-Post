import './style.scss';

export default function ProgressBar({ value }) {
    
    return(
        <div className='bca-progress-container'>
            <div className="bca-progress">
                <progress value={value} max="100"> {value} </progress>
            </div>
        </div>
    )
}